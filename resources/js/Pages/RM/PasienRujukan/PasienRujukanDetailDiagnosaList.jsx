import React, { useState, useEffect } from "react";
import { Modal, Card, Select, AutoComplete, Row, Col } from "antd";
import { notification } from "antd";
import { Table, Skeleton, Button } from "antd";
import axios from "axios";

export default function Index({
    pasien,
    diagnosa,
    fetchDiagnosa,
    loadingFetchDiagnosa,
    showDeleteConfirm,
    selectedDiagnosa, //diagnosa yang sudah disimpan di database
    setSelectedDiagnosa,
    isModalHapusDiagnosaOpen,
    handleCancelDelDiagnosa,
    currentDiagnosa,
    deleteDiagnosa,
}) {
    const columns = [
        {
            title: "Status",
            dataIndex: "MRPSTAT_DIAG",
            key: "ID",
        },
        {
            title: "Lama/Baru",
            dataIndex: "MRPKASUS",
            key: "ID",
        },
        {
            title: "Kode",
            dataIndex: "MRPKD_PENYAKIT",
            key: "MRPKD_PENYAKIT",
        },
        {
            title: "Penyakit",
            dataIndex: "PENYAKIT",
            key: "PENYAKIT",
        },
        {
            title: "Action",
            key: "action",
            render: (_, record) => (
                <Button
                    size="small"
                    variant="outlined"
                    color="danger"
                    onClick={() => showDeleteConfirm(record)}
                >
                    hapus
                </Button>
            ),
        },
    ];

    const [anotherOptions, setAnotherOptions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [selectedDiagnosaForm, setSelectedDiagnosaForm] = useState(null);
    const [selectedStatusDiagForm, setSelectedStatusDiagForm] = useState(null);
    const [selectedKasusForm, setSelectedKasusForm] = useState(null);
    const [loadingSaveDiag, setLoadingSaveDiag] = useState(false); // Loading state for each diagnosa

    // Fetch diagnosa with lazy loading support
    const fetchSugetDiagnosa = async (query, pageNumber) => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.cari_penyakit"),
                {
                    query,
                    page: pageNumber,
                }
            );
            // If no results, mark hasMore as false
            if (response.data.data.length === 0) {
                setHasMore(false);
            }
            // If it's the first page, reset the results, otherwise append new results
            if (pageNumber === 1) {
                setAnotherOptions(response.data.data);
            } else {
                setAnotherOptions((prev) => [...prev, ...response.data.data]);
            }
            setPage(pageNumber); // Update the current page
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoading(false);
        }
    };

    // Lazy load when the user scrolls to the bottom
    const onScroll = (e) => {
        const bottom =
            e.target.scrollHeight ===
            e.target.scrollTop + e.target.clientHeight;
        if (bottom && hasMore && !loading) {
            // If scrolled to bottom and more data is available, load the next page
            fetchSugetDiagnosa(value, page + 1);
        }
    };

    // Function to save kode diagnosa
    const saveDiagnosa = async () => {
        setLoadingSaveDiag(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_diagnosa"),
                {
                    icd10_code: selectedDiagnosaForm,
                    no_transaksikj: pasien.FRPNOTRANSAKSIKJ,
                    no_rm: pasien.FRPPASIEN_ID,
                    kd_unit: pasien.FRPUNIT,
                    tgl_masuk: pasien.FRPTGL,
                    status_diagnosa: selectedStatusDiagForm,
                    kasus: selectedKasusForm,
                }
            );

            if (response?.data?.status === "ok") {
                notification.success({
                    placement: "bottomRight",
                    message: "Sukses!",
                    description: "Diagnosa berhasil ditambahkan.",
                });
            }
            return
        } catch (error) {
            console.error("Error saving diagnosa:", error);
        } finally {
            fetchDiagnosa();
            setLoadingSaveDiag(false);
            setSelectedDiagnosaForm(null);
            setSelectedStatusDiagForm(null);
            setSelectedKasusForm(null);
        }
        return
    };

    return (
        <Card title={`Diagnosa`}>
            <Row gutter={16} style={{ marginBottom: 10 }}>
                <Col span={5}>
                    <Select
                        showSearch
                        style={{ width: "100%" }}
                        placeholder="STATUS DIAGNOSA"
                        filterOption={(input, option) =>
                            (option?.label ?? "")
                                .toLowerCase()
                                .includes(input.toLowerCase())
                        }
                        options={[
                            { value: "5", label: "5-Diagnosa Akhir" },
                            { value: "1", label: "1-Diagnosa Lain" },
                            { value: "2", label: "2-Komplikasi" },
                            { value: "0", label: "0-Diagnosa Awal" },
                            { value: "3", label: "3-Penyebab Luar" },
                            { value: "4", label: "4-Penyebeb Kematian" },
                        ]}
                        onChange={(value) => {
                            setSelectedStatusDiagForm(value);
                        }}
                    />
                </Col>
                <Col span={3}>
                    <Select
                        showSearch
                        style={{ width: "100%" }}
                        placeholder="Lama Baru"
                        filterOption={(input, option) =>
                            (option?.label ?? "")
                                .toLowerCase()
                                .includes(input.toLowerCase())
                        }
                        options={[
                            { value: "0", label: "Baru" },
                            { value: "1", label: "Lama" },
                        ]}
                        onChange={(value) => {
                            setSelectedKasusForm(value);
                        }}
                    />
                </Col>
                <Col span={12}>
                    <AutoComplete
                        allowClear
                        onChange={() => setSelectedDiagnosaForm(null)}
                        options={anotherOptions.map((item) => ({
                            value: `${item.KD_PENYAKIT} - ${item.PENYAKIT}`, // Display kode penyakit and nama penyakit
                            label: (
                                <div style={{ wordBreak: "break-word" }}>
                                    <strong>{item.KD_PENYAKIT}</strong> -{" "}
                                    <span style={{ wordBreak: "break-word" }}>
                                        {item.PENYAKIT}
                                    </span>
                                </div>
                            ),
                            disabled: selectedDiagnosa.includes(
                                item.KD_PENYAKIT
                            ), // Disable if already selected
                        }))}
                        style={{ width: "100%" }}
                        onSelect={(value) => {
                            const kdPenyakit = value.split(" - ")[0]; // Extract KD_PENYAKIT from "KD_PENYAKIT - PENYAKIT"
                            setSelectedDiagnosaForm(kdPenyakit); // Pass the KD_PENYAKIT to your onSelect handler
                        }}
                        onSearch={(text) => fetchSugetDiagnosa(text, 1)}
                        placeholder="Control mode"
                        onScroll={onScroll} // Attach scroll event for lazy loading
                    />
                </Col>
                <Col span={4}>
                    <Button
                        loading={loadingSaveDiag}
                        type="primary"
                        size="medium"
                        style={{ width: "100%" }}
                        onClick={saveDiagnosa}
                        disabled={
                            loadingSaveDiag ||
                            selectedKasusForm === null ||
                            selectedStatusDiagForm === null ||
                            selectedDiagnosaForm === null
                        }
                    >
                        Tambah Penyakit
                    </Button>
                </Col>
            </Row>
            <>
                {loadingFetchDiagnosa ? (
                    <>
                        <Skeleton />
                    </>
                ) : (
                    <>
                        <Table
                            pagination={false}
                            columns={columns}
                            dataSource={diagnosa}
                            size="small"
                        />
                    </>
                )}
            </>
            {/* Modal for Confirming Deletion */}
            <Modal
                title="Hapus Diagnosa"
                open={isModalHapusDiagnosaOpen}
                onOk={() => {
                    currentDiagnosa &&
                        deleteDiagnosa(
                            currentDiagnosa.ID,
                            currentDiagnosa.MRPKD_PENYAKIT
                        );
                }}
                onCancel={handleCancelDelDiagnosa}
                okText="Ya"
                cancelText="Tidak"
                okButtonProps={{ danger: true }}
            >
                <p>Apakah anda yakin ingin menghapus diagnosa ini?</p>
            </Modal>
        </Card>
    );
}

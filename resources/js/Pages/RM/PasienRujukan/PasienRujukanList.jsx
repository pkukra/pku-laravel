import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import {
    Card,
    Button,
    Table,
    Row,
    Col,
    DatePicker,
    Input,
    Select,
    Typography,
} from "antd";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";

export default function Index({ auth }) {
    const queryParams = new URLSearchParams(window.location.search);

    const initialPage = parseInt(queryParams.get("page")) || 1;
    const initialPerPage = parseInt(queryParams.get("per_page")) || 100;
    const initialDate =
        queryParams.get("date") || moment().format("YYYY-MM-DD");
    const initialKodePoly = queryParams.get("kode_poly") || "";
    const initialKodeDokter = queryParams.get("kode_dokter") || "";
    const initialNoRM = queryParams.get("no_rm") || "";
    const initialIsInacbgFinal =
        queryParams.get("is_inacbg_final") || "not_final";
    const [isInacbgFinal, setIsInacbgFinal] = useState(initialIsInacbgFinal);

    const [loading, setLoading] = useState(false);
    const [dataPasienRujukans, setDataPasienRujukans] = useState([]);
    const [totalData, setTotalData] = useState(0);
    const [page, setPage] = useState(initialPage);
    const [perPage, setPerPage] = useState(initialPerPage);
    const [date, setDate] = useState(initialDate);
    const [kodePoly, setKodePoly] = useState(initialKodePoly);
    const [kodeDokter, setKodeDokter] = useState(initialKodeDokter);
    const [noRM, setNoRM] = useState(initialNoRM);

    const columnsRujukan = [
        {
            title: "Tgl Jam Periksa",
            dataIndex: "FRPTGL",
            render: (_, record) => (
                <>
                    {moment(record.FRPTGL).format("DD/MM/YYYY")}{" "}
                    {moment(record.FRPJAM).format("HH:mm")}
                </>
            ),
        },
        {
            title: "No RM / Nama Pasien",
            dataIndex: "NAMAPASIEN",
            render: (_, record) => (
                <>
                    {record.FRPPASIEN_ID} /<br />
                    {record.NAMAPASIEN}
                </>
            ),
        },
        {
            title: "Kode Poly",
            dataIndex: "FRPUNIT",
            key: "FRPUNIT",
        },
        {
            title: "Nama Poly",
            dataIndex: "FMPKLINIKN",
            key: "FMPKLINIKN",
        },
        {
            title: "Kode Dokter",
            dataIndex: "FRPDOKTER_ID",
            key: "FRPDOKTER_ID",
        },
        {
            title: "Dokter",
            dataIndex: "FMDDOKTERN",
            key: "FMDDOKTERN",
        },
        {
            title: "Kelompok",
            dataIndex: "FRPCUSTOMER_ID",
            key: "FRPCUSTOMER_ID",
        },
        {
            title: "Final INACBG",
            dataIndex: "IS_INACBG_FINAL",
            key: "IS_INACBG_FINAL",
            align: "center",
            render: (_, record) => <>{record?.IS_INACBG_FINAL ? "✅" : "❌"}</>,
        },
        {
            title: "Action",
            dataIndex: "action",
            key: "action",
            render: (_, record) => (
                <a
                    href={route("rm.pasien-rujukan.detail", {
                        kode_reg: record.FRPNOTRANSAKSIKJ,
                    })}
                >
                    <Button type="primary" size="small">
                        Tampilkan
                    </Button>
                </a>
            ),
        },
    ];

    const fetchDataPasienRujukan = async (
        pageVal = page,
        perPageVal = perPage
    ) => {
        setLoading(true);
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.list_rujukan_data"),
                {
                    params: {
                        date,
                        page: pageVal,
                        per_page: perPageVal,
                        kode_poly: kodePoly,
                        kode_dokter: kodeDokter,
                        no_rm: noRM,
                        is_inacbg_final: isInacbgFinal,
                    },
                }
            );
            setDataPasienRujukans(response?.data?.data?.data || []);
            setTotalData(response?.data?.data?.total || 0);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoading(false);
        }
    };

    const handleTableChange = (pagination) => {
        const newPage = pagination.current;
        const newPerPage = pagination.pageSize;

        const params = new URLSearchParams(window.location.search);
        params.set("page", newPage);
        params.set("per_page", newPerPage);
        params.set("date", date);
        params.set("kode_poly", kodePoly);
        params.set("kode_dokter", kodeDokter);
        params.set("no_rm", noRM); // Update No RM in URL
        window.history.replaceState(null, "", `?${params.toString()}`);

        setPage(newPage);
        setPerPage(newPerPage);
        fetchDataPasienRujukan(newPage, newPerPage);
    };

    useEffect(() => {
        fetchDataPasienRujukan();
    }, []);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <p className="font-semibold text-lg text-gray-800 leading-tight">
                    List Kunjungan Pasien Rujukan
                </p>
            }
        >
            <Head title="List Kunjungan Pasien Rujukan" />
            <Card title="Pasien Rawat Jalan" style={{ marginBottom: 5 }}>
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={3}>
                        <div>
                            <Typography.Text strong>Tanggal</Typography.Text>
                        </div>
                        <DatePicker
                            allowClear={false}
                            value={dayjs(date)}
                            onChange={(dateMoment, dateString) =>
                                setDate(dateString)
                            }
                            placeholder="Pilih tanggal"
                            disabledDate={(current) =>
                                current && current > moment().endOf("day")
                            }
                        />
                    </Col>
                    <Col span={3}>
                        <div>
                            <Typography.Text strong>No RM</Typography.Text>
                        </div>
                        <Input
                            allowClear
                            placeholder="No RM"
                            value={noRM}
                            onChange={(e) => setNoRM(e.target.value)}
                        />
                    </Col>
                    <Col span={3}>
                        <div>
                            <Typography.Text strong>Kode Poli</Typography.Text>
                        </div>
                        <Input
                            allowClear
                            placeholder="Kode Poli"
                            value={kodePoly}
                            onChange={(e) => setKodePoly(e.target.value)}
                        />
                    </Col>
                    <Col span={3}>
                        <div>
                            <Typography.Text strong>
                                Kode Dokter
                            </Typography.Text>
                        </div>
                        <Input
                            allowClear
                            placeholder="Kode Dokter"
                            value={kodeDokter}
                            onChange={(e) => setKodeDokter(e.target.value)}
                        />
                    </Col>
                    <Col span={4}>
                        <div>
                            <Typography.Text strong>
                                Filter Final INACBG
                            </Typography.Text>
                        </div>
                        <Select
                            style={{ width: "100%" }}
                            value={isInacbgFinal}
                            onChange={(value) => setIsInacbgFinal(value)}
                            options={[
                                { label: "Sudah Di-Final", value: "final" },
                                { label: "Belum Di-Final", value: "not_final" },
                            ]}
                            placeholder="Filter Final INACBG"
                        />
                    </Col>
                    <Col span={2}>
                        <div>
                            <Typography.Text>&nbsp;</Typography.Text>
                        </div>
                        <Button
                            block
                            type="primary"
                            onClick={() => {
                                const params = new URLSearchParams(
                                    window.location.search
                                );
                                params.set("page", 1);
                                params.set("per_page", perPage);
                                params.set("date", date);
                                params.set("kode_poly", kodePoly);
                                params.set("kode_dokter", kodeDokter);
                                params.set("no_rm", noRM);
                                params.set("is_inacbg_final", isInacbgFinal);
                                window.history.replaceState(
                                    null,
                                    "",
                                    `?${params.toString()}`
                                );
                                setPage(1);
                                fetchDataPasienRujukan(1, perPage);
                            }}
                        >
                            Cari
                        </Button>
                    </Col>
                    <Col span={2}>
                        <div>
                            <Typography.Text>&nbsp;</Typography.Text>
                        </div>
                        <Button
                            block
                            onClick={() => {
                                window.location.replace(
                                    `${route("rm.pasien-rujukan.list_rujukan")}`
                                );
                            }}
                        >
                            Reset
                        </Button>
                    </Col>
                </Row>

                <small>
                    total data: {totalData}. Page: {page}. Perpage: {perPage}
                </small>

                <Table
                    dataSource={dataPasienRujukans}
                    columns={columnsRujukan}
                    size="small"
                    loading={loading}
                    rowKey="FRPNOTRANSAKSIKJ"
                    scroll={{ x: "max-content" }}
                    pagination={{
                        simple: true,
                        current: page,
                        total: totalData,
                        pageSize: perPage,
                    }}
                    onChange={handleTableChange}
                />
            </Card>
        </AuthenticatedLayout>
    );
}

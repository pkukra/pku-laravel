import React, { useState } from "react";
import { Popover, Row, Col, Button  } from "antd";

export default function Index({ namapasien, nomer_rm, kode_reg }) {
    const [open, setOpen] = useState(false);
    const hide = () => {
        setOpen(false);
    };
    const handleOpenChange = (newOpen) => {
        setOpen(newOpen);
    };

    return (
        <>
            <Popover
                title={`${namapasien} - ${nomer_rm}`}
                trigger="click"
                content={
                    <Row gutter={[8, 8]} style={{ width: 250 }}>
                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify({ kode_reg }))}
                            >
                                SEP
                            </Button>
                        </Col>

                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify(namapasien))}
                            >
                                Resume
                            </Button>
                        </Col>

                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify(namapasien))}
                            >
                                Grouping
                            </Button>
                        </Col>

                        <Col span={12}>
                            <Button
                                block
                                size="small"
                                onClick={() => alert(JSON.stringify(namapasien))}
                            >
                                Klaim
                            </Button>
                        </Col>
                    </Row>
                }
            >
                <Button type="primary" size="small">
                    Klaim
                </Button>
            </Popover>
        </>
    );
}
